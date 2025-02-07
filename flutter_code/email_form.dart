import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

class EmailForm extends StatefulWidget {
  @override
  _EmailFormState createState() => _EmailFormState();
}

class _EmailFormState extends State<EmailForm> {
  final TextEditingController _emailController = TextEditingController();
  bool _isLoading = false;
  String? _message;

  Future<void> _sendEmail() async {
    final String email = _emailController.text.trim();

    if (email.isEmpty || !email.contains("@")) {
      setState(() {
        _message = "Please enter a valid email address.";
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _message = null;
    });

    try {
      final response = await http.post(
        Uri.parse('http://${CONFIG.server}/PRS/flutter_util/email.php'),
        body: {'email': email},
      );

      setState(() {
        _message = response.statusCode == 200
            ? "Email sent successfully!"
            : "Failed to send email. Try again.";
      });
    } catch (error) {
      setState(() {
        _message = "Error: Unable to connect to the server.";
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text("Send Email")),
      body: Padding(
        padding: EdgeInsets.all(20),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(
                labelText: "Enter your email",
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 20),
            _isLoading
                ? CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _sendEmail,
                    child: Text("Send Email"),
                  ),
            if (_message != null)
              Padding(
                padding: const EdgeInsets.all(8.0),
                child: Text(
                  _message!,
                  style: TextStyle(
                    color: _message == "Email sent successfully!"
                        ? Colors.green
                        : Colors.red,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
